import mongoose from "mongoose";
import { IUser } from "../userTypes";
const userSchema = new mongoose.Schema<IUser>(
    {
        firstName: {
            type: String,
            required: true,
            trim: true,
        },

        middleName: {
            type: String,
            trim: true,
            default: "", // optional field
        },

        lastName: {
            type: String,
            required: true,
            trim: true,
        },

        userName: {
            type: String,
            required: true,
            unique: true,
            lowercase: true,
            trim: true,
        },

        email: {
            type: String,
            required: true,
            unique: true,
            lowercase: true,
            trim: true,
        },

        password: {
            type: String,
            required: true,
            minlength: 8,
        },

        role: {
            type: String,
            enum: ["user", "admin", "superadmin"],
            default: "user",
        },

        status: {
            type: String,
            enum: ["active", "inactive", "suspended"],
            default: "active",
        },

        trash: {
            type: Boolean,
            default: false,
        },
    },
    {
        timestamps: true,
        collection: "tbl_users",
    }
);


// OR pass collection name here
const User = mongoose.model<IUser>("userModel", userSchema); // schema already includes collection name

export default User;
