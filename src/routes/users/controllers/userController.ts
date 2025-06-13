import { validationResult } from "express-validator";
import { config } from "../../../config/config";
import User from "../models/userModel";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import { Request, Response } from "express";

// Define JWT Payload interface for better typing
interface JwtPayload {
  userId: string;
  email: string;
  role: string;
}

// Create User
const createUsers = async (req: Request, res: Response): Promise<void> => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    res.status(422).json({
      status: 422,
      message: "Validation failed",
      errors: errors.array(),
    });
    return;
  }

  const { firstName, lastName, userName, email, password, role, status } = req.body;

  if (!password || password.trim().length < 6) {
    res.status(400).json({
      status: 400,
      message: "Password is required and must be at least 6 characters",
    });
    return;
  }

  try {
    const existingUser = await User.findOne({
      $or: [{ email: email.toLowerCase() }, { userName }],
    });

    if (existingUser) {
      let message = "User already exists";
      if (existingUser.email === email.toLowerCase() && existingUser.userName === userName) {
        message = "Email and Username already in use";
      } else if (existingUser.email === email.toLowerCase()) {
        message = "Email already in use";
      } else if (existingUser.userName === userName) {
        message = "Username already taken";
      }

      res.status(400).json({ status: 400, message });
      return;
    }

    const hashedPassword = await bcrypt.hash(password, 10);

    const newUser = new User({
      firstName,
      lastName,
      userName,
      email: email.toLowerCase(),
      password: hashedPassword,
      role,
      status,
    });

    await newUser.save();

    const payload: JwtPayload = {
      userId: newUser._id.toString(),
      email: newUser.email,
      role: newUser.role,
    };

    const jwtSecret = config.jwt.secret;
    if (!jwtSecret) {
      throw new Error("JWT secret is not defined in config");
    }

    const token = jwt.sign(payload, jwtSecret, { expiresIn: "7d" });

    res.status(201).json({
      status: 201,
      message: "User created successfully",
    //   data: {
    //     id: newUser._id,
    //     firstName: newUser.firstName,
    //     lastName: newUser.lastName,
    //     userName: newUser.userName,
    //     email: newUser.email,
    //     role: newUser.role,
    //     status: newUser.status,
    //   },
      token,
    });
  } catch (error) {
    console.error("Error creating user:", error);
    res.status(500).json({
      status: 500,
      message: "Server error",
    });
  }
};

// Login User
const loginUsers = async (req: Request, res: Response): Promise<void> => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    res.status(422).json({
      status: 422,
      message: "Validation failed",
      errors: errors.array(),
    });
    return;
  }

  const { email, password } = req.body;

  try {
    const user = await User.findOne({ email: email.toLowerCase() });

    if (!user) {
      res.status(401).json({
        status: 401,
        message: "Invalid email or password",
      });
      return;
    }

    const isMatch = await bcrypt.compare(password, user.password);

    if (!isMatch) {
      res.status(401).json({
        status: 401,
        message: "Invalid email or password",
      });
      return;
    }

    const payload: JwtPayload = {
      userId: user._id.toString(),
      email: user.email,
      role: user.role,
    };

    const jwtSecret = config.jwt.secret;
    if (!jwtSecret) {
      throw new Error("JWT secret is not defined in config");
    }

    const token = jwt.sign(payload, jwtSecret, { expiresIn: "7d" });

    res.status(200).json({
      status: 200,
      message: "Login successful",
      token,
    //   data: {
    //     id: user._id,
    //     firstName: user.firstName,
    //     lastName: user.lastName,
    //     userName: user.userName,
    //     email: user.email,
    //     role: user.role,
    //     status: user.status,
    //   },
    });
  } catch (error) {
    console.error("Login error:", error);
    res.status(500).json({
      status: 500,
      message: "Server error",
    });
  }
};

export { createUsers, loginUsers };
