// userController.ts
import { Request, Response } from "express";

export const createUsers = async (req: Request, res: Response) => {
    try {
        const { name, email, password } = req.body;

        // Basic validation
        if (!name || !email || !password) {
            res.status(400).json({ msg: "Name, email, and password are required." });
        }

        // Simulate user creation (replace with real DB logic)
        const newUser = {
            id: Date.now(), // Just for demonstration
            name,
            email,
            password, // In real apps, NEVER store plain passwords
        };

        console.log("User created:", newUser);

        res.status(201).json({ msg: "User created successfully", user: newUser });
    } catch (error) {
        console.error("Error creating user:", error);
        res.status(500).json({ msg: "Internal server error" });
    }
};


module.exports = { createUsers };
